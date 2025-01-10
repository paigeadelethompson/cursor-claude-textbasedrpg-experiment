namespace Game\GraphQL\Resolvers;

use Game\Training\SatanicShrine;

class TrainingResolver {
    private $player;
    private $shrine;

    public function __construct($player) {
        $this->player = $player;
        $this->shrine = new SatanicShrine($player);
    }

    public function sacrifice($root, array $args): array {
        $stat = $args['stat'];
        $energy = $args['energy'];

        return $this->shrine->sacrifice($stat, $energy);
    }
} 